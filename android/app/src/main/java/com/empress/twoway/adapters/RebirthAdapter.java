package com.empress.twoway.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.empress.twoway.R;
import com.empress.twoway.models.RebirthNode;

import java.util.List;

public class RebirthAdapter extends RecyclerView.Adapter<RebirthAdapter.ViewHolder> {

    private final List<RebirthNode> list;

    public RebirthAdapter(List<RebirthNode> list) {
        this.list = list;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_rebirth, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        RebirthNode node = list.get(position);
        holder.tvName.setText(node.getName());
        holder.tvNodeId.setText(String.format("%s | Placement Parent: %s", node.getMemberId(), node.getPlacementParentId()));
        holder.tvBadge.setText(node.getStatus());
    }

    @Override
    public int getItemCount() {
        return list.size();
    }

    static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvName, tvNodeId, tvBadge;

        ViewHolder(View itemView) {
            super(itemView);
            tvName = itemView.findViewById(R.id.tvRebirthName);
            tvNodeId = itemView.findViewById(R.id.tvRebirthNodeId);
            tvBadge = itemView.findViewById(R.id.tvRebirthBadge);
        }
    }
}
